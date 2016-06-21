/**
 * CopyObjectResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class CopyObjectResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.MetaData metaData;

    private com.woodwing.enterprise.interfaces.services.wfl.Relation[] relations;

    private com.woodwing.enterprise.interfaces.services.wfl.Target[] targets;

    public CopyObjectResponse() {
    }

    public CopyObjectResponse(
           com.woodwing.enterprise.interfaces.services.wfl.MetaData metaData,
           com.woodwing.enterprise.interfaces.services.wfl.Relation[] relations,
           com.woodwing.enterprise.interfaces.services.wfl.Target[] targets) {
           this.metaData = metaData;
           this.relations = relations;
           this.targets = targets;
    }


    /**
     * Gets the metaData value for this CopyObjectResponse.
     * 
     * @return metaData
     */
    public com.woodwing.enterprise.interfaces.services.wfl.MetaData getMetaData() {
        return metaData;
    }


    /**
     * Sets the metaData value for this CopyObjectResponse.
     * 
     * @param metaData
     */
    public void setMetaData(com.woodwing.enterprise.interfaces.services.wfl.MetaData metaData) {
        this.metaData = metaData;
    }


    /**
     * Gets the relations value for this CopyObjectResponse.
     * 
     * @return relations
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Relation[] getRelations() {
        return relations;
    }


    /**
     * Sets the relations value for this CopyObjectResponse.
     * 
     * @param relations
     */
    public void setRelations(com.woodwing.enterprise.interfaces.services.wfl.Relation[] relations) {
        this.relations = relations;
    }


    /**
     * Gets the targets value for this CopyObjectResponse.
     * 
     * @return targets
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Target[] getTargets() {
        return targets;
    }


    /**
     * Sets the targets value for this CopyObjectResponse.
     * 
     * @param targets
     */
    public void setTargets(com.woodwing.enterprise.interfaces.services.wfl.Target[] targets) {
        this.targets = targets;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof CopyObjectResponse)) return false;
        CopyObjectResponse other = (CopyObjectResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.metaData==null && other.getMetaData()==null) || 
             (this.metaData!=null &&
              this.metaData.equals(other.getMetaData()))) &&
            ((this.relations==null && other.getRelations()==null) || 
             (this.relations!=null &&
              java.util.Arrays.equals(this.relations, other.getRelations()))) &&
            ((this.targets==null && other.getTargets()==null) || 
             (this.targets!=null &&
              java.util.Arrays.equals(this.targets, other.getTargets())));
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
        if (getMetaData() != null) {
            _hashCode += getMetaData().hashCode();
        }
        if (getRelations() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRelations());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRelations(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getTargets() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getTargets());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getTargets(), i);
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
        new org.apache.axis.description.TypeDesc(CopyObjectResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">CopyObjectResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("metaData");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MetaData"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "MetaData"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("relations");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Relations"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Relation"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Relation"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("targets");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Targets"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Target"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Target"));
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
