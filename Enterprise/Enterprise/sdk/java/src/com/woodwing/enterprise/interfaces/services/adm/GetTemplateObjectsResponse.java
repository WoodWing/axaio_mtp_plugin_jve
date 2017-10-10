/**
 * GetTemplateObjectsResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class GetTemplateObjectsResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.adm.TemplateObjectAccess[] templateObjects;

    private com.woodwing.enterprise.interfaces.services.adm.UserGroup[] userGroups;

    private com.woodwing.enterprise.interfaces.services.adm.ObjectInfo[] objectInfos;

    public GetTemplateObjectsResponse() {
    }

    public GetTemplateObjectsResponse(
           com.woodwing.enterprise.interfaces.services.adm.TemplateObjectAccess[] templateObjects,
           com.woodwing.enterprise.interfaces.services.adm.UserGroup[] userGroups,
           com.woodwing.enterprise.interfaces.services.adm.ObjectInfo[] objectInfos) {
           this.templateObjects = templateObjects;
           this.userGroups = userGroups;
           this.objectInfos = objectInfos;
    }


    /**
     * Gets the templateObjects value for this GetTemplateObjectsResponse.
     * 
     * @return templateObjects
     */
    public com.woodwing.enterprise.interfaces.services.adm.TemplateObjectAccess[] getTemplateObjects() {
        return templateObjects;
    }


    /**
     * Sets the templateObjects value for this GetTemplateObjectsResponse.
     * 
     * @param templateObjects
     */
    public void setTemplateObjects(com.woodwing.enterprise.interfaces.services.adm.TemplateObjectAccess[] templateObjects) {
        this.templateObjects = templateObjects;
    }


    /**
     * Gets the userGroups value for this GetTemplateObjectsResponse.
     * 
     * @return userGroups
     */
    public com.woodwing.enterprise.interfaces.services.adm.UserGroup[] getUserGroups() {
        return userGroups;
    }


    /**
     * Sets the userGroups value for this GetTemplateObjectsResponse.
     * 
     * @param userGroups
     */
    public void setUserGroups(com.woodwing.enterprise.interfaces.services.adm.UserGroup[] userGroups) {
        this.userGroups = userGroups;
    }


    /**
     * Gets the objectInfos value for this GetTemplateObjectsResponse.
     * 
     * @return objectInfos
     */
    public com.woodwing.enterprise.interfaces.services.adm.ObjectInfo[] getObjectInfos() {
        return objectInfos;
    }


    /**
     * Sets the objectInfos value for this GetTemplateObjectsResponse.
     * 
     * @param objectInfos
     */
    public void setObjectInfos(com.woodwing.enterprise.interfaces.services.adm.ObjectInfo[] objectInfos) {
        this.objectInfos = objectInfos;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetTemplateObjectsResponse)) return false;
        GetTemplateObjectsResponse other = (GetTemplateObjectsResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.templateObjects==null && other.getTemplateObjects()==null) || 
             (this.templateObjects!=null &&
              java.util.Arrays.equals(this.templateObjects, other.getTemplateObjects()))) &&
            ((this.userGroups==null && other.getUserGroups()==null) || 
             (this.userGroups!=null &&
              java.util.Arrays.equals(this.userGroups, other.getUserGroups()))) &&
            ((this.objectInfos==null && other.getObjectInfos()==null) || 
             (this.objectInfos!=null &&
              java.util.Arrays.equals(this.objectInfos, other.getObjectInfos())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getTemplateObjects() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getTemplateObjects());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getTemplateObjects(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getUserGroups() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getUserGroups());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getUserGroups(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getObjectInfos() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getObjectInfos());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getObjectInfos(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(GetTemplateObjectsResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetTemplateObjectsResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("templateObjects");
        elemField.setXmlName(new javax.xml.namespace.QName("", "TemplateObjects"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "TemplateObjectAccess"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "TemplateObjectAccess"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("userGroups");
        elemField.setXmlName(new javax.xml.namespace.QName("", "UserGroups"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "UserGroup"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "UserGroup"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("objectInfos");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ObjectInfos"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ObjectInfo"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ObjectInfo"));
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
