/**
 * ModifyAccessProfilesResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class ModifyAccessProfilesResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.adm.AccessProfile[] accessProfiles;

    public ModifyAccessProfilesResponse() {
    }

    public ModifyAccessProfilesResponse(
           com.woodwing.enterprise.interfaces.services.adm.AccessProfile[] accessProfiles) {
           this.accessProfiles = accessProfiles;
    }


    /**
     * Gets the accessProfiles value for this ModifyAccessProfilesResponse.
     * 
     * @return accessProfiles
     */
    public com.woodwing.enterprise.interfaces.services.adm.AccessProfile[] getAccessProfiles() {
        return accessProfiles;
    }


    /**
     * Sets the accessProfiles value for this ModifyAccessProfilesResponse.
     * 
     * @param accessProfiles
     */
    public void setAccessProfiles(com.woodwing.enterprise.interfaces.services.adm.AccessProfile[] accessProfiles) {
        this.accessProfiles = accessProfiles;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof ModifyAccessProfilesResponse)) return false;
        ModifyAccessProfilesResponse other = (ModifyAccessProfilesResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.accessProfiles==null && other.getAccessProfiles()==null) || 
             (this.accessProfiles!=null &&
              java.util.Arrays.equals(this.accessProfiles, other.getAccessProfiles())));
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
        if (getAccessProfiles() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getAccessProfiles());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getAccessProfiles(), i);
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
        new org.apache.axis.description.TypeDesc(ModifyAccessProfilesResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">ModifyAccessProfilesResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("accessProfiles");
        elemField.setXmlName(new javax.xml.namespace.QName("", "AccessProfiles"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "AccessProfile"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "AccessProfile"));
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