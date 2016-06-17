/**
 * EntityTags.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class EntityTags  implements java.io.Serializable {
    private java.lang.String entity;

    private com.woodwing.enterprise.interfaces.services.wfl.AutoSuggestTag[] tags;

    public EntityTags() {
    }

    public EntityTags(
           java.lang.String entity,
           com.woodwing.enterprise.interfaces.services.wfl.AutoSuggestTag[] tags) {
           this.entity = entity;
           this.tags = tags;
    }


    /**
     * Gets the entity value for this EntityTags.
     * 
     * @return entity
     */
    public java.lang.String getEntity() {
        return entity;
    }


    /**
     * Sets the entity value for this EntityTags.
     * 
     * @param entity
     */
    public void setEntity(java.lang.String entity) {
        this.entity = entity;
    }


    /**
     * Gets the tags value for this EntityTags.
     * 
     * @return tags
     */
    public com.woodwing.enterprise.interfaces.services.wfl.AutoSuggestTag[] getTags() {
        return tags;
    }


    /**
     * Sets the tags value for this EntityTags.
     * 
     * @param tags
     */
    public void setTags(com.woodwing.enterprise.interfaces.services.wfl.AutoSuggestTag[] tags) {
        this.tags = tags;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof EntityTags)) return false;
        EntityTags other = (EntityTags) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.entity==null && other.getEntity()==null) || 
             (this.entity!=null &&
              this.entity.equals(other.getEntity()))) &&
            ((this.tags==null && other.getTags()==null) || 
             (this.tags!=null &&
              java.util.Arrays.equals(this.tags, other.getTags())));
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
        if (getEntity() != null) {
            _hashCode += getEntity().hashCode();
        }
        if (getTags() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getTags());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getTags(), i);
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
        new org.apache.axis.description.TypeDesc(EntityTags.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "EntityTags"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("entity");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Entity"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("tags");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Tags"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "AutoSuggestTag"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "AutoSuggestTag"));
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
