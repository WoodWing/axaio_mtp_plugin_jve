/**
 * Relation.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class Relation  implements java.io.Serializable {
    private java.lang.String parent;

    private java.lang.String child;

    private com.woodwing.enterprise.interfaces.services.wfl.RelationType type;

    private com.woodwing.enterprise.interfaces.services.wfl.Placement[] placements;

    private java.lang.String parentVersion;

    private java.lang.String childVersion;

    private com.woodwing.enterprise.interfaces.services.wfl.Attachment geometry;

    private org.apache.axis.types.UnsignedInt rating;

    private com.woodwing.enterprise.interfaces.services.wfl.Target[] targets;

    private com.woodwing.enterprise.interfaces.services.wfl.ObjectInfo parentInfo;

    private com.woodwing.enterprise.interfaces.services.wfl.ObjectInfo childInfo;

    private com.woodwing.enterprise.interfaces.services.wfl.ObjectLabel[] objectLabels;

    public Relation() {
    }

    public Relation(
           java.lang.String parent,
           java.lang.String child,
           com.woodwing.enterprise.interfaces.services.wfl.RelationType type,
           com.woodwing.enterprise.interfaces.services.wfl.Placement[] placements,
           java.lang.String parentVersion,
           java.lang.String childVersion,
           com.woodwing.enterprise.interfaces.services.wfl.Attachment geometry,
           org.apache.axis.types.UnsignedInt rating,
           com.woodwing.enterprise.interfaces.services.wfl.Target[] targets,
           com.woodwing.enterprise.interfaces.services.wfl.ObjectInfo parentInfo,
           com.woodwing.enterprise.interfaces.services.wfl.ObjectInfo childInfo,
           com.woodwing.enterprise.interfaces.services.wfl.ObjectLabel[] objectLabels) {
           this.parent = parent;
           this.child = child;
           this.type = type;
           this.placements = placements;
           this.parentVersion = parentVersion;
           this.childVersion = childVersion;
           this.geometry = geometry;
           this.rating = rating;
           this.targets = targets;
           this.parentInfo = parentInfo;
           this.childInfo = childInfo;
           this.objectLabels = objectLabels;
    }


    /**
     * Gets the parent value for this Relation.
     * 
     * @return parent
     */
    public java.lang.String getParent() {
        return parent;
    }


    /**
     * Sets the parent value for this Relation.
     * 
     * @param parent
     */
    public void setParent(java.lang.String parent) {
        this.parent = parent;
    }


    /**
     * Gets the child value for this Relation.
     * 
     * @return child
     */
    public java.lang.String getChild() {
        return child;
    }


    /**
     * Sets the child value for this Relation.
     * 
     * @param child
     */
    public void setChild(java.lang.String child) {
        this.child = child;
    }


    /**
     * Gets the type value for this Relation.
     * 
     * @return type
     */
    public com.woodwing.enterprise.interfaces.services.wfl.RelationType getType() {
        return type;
    }


    /**
     * Sets the type value for this Relation.
     * 
     * @param type
     */
    public void setType(com.woodwing.enterprise.interfaces.services.wfl.RelationType type) {
        this.type = type;
    }


    /**
     * Gets the placements value for this Relation.
     * 
     * @return placements
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Placement[] getPlacements() {
        return placements;
    }


    /**
     * Sets the placements value for this Relation.
     * 
     * @param placements
     */
    public void setPlacements(com.woodwing.enterprise.interfaces.services.wfl.Placement[] placements) {
        this.placements = placements;
    }


    /**
     * Gets the parentVersion value for this Relation.
     * 
     * @return parentVersion
     */
    public java.lang.String getParentVersion() {
        return parentVersion;
    }


    /**
     * Sets the parentVersion value for this Relation.
     * 
     * @param parentVersion
     */
    public void setParentVersion(java.lang.String parentVersion) {
        this.parentVersion = parentVersion;
    }


    /**
     * Gets the childVersion value for this Relation.
     * 
     * @return childVersion
     */
    public java.lang.String getChildVersion() {
        return childVersion;
    }


    /**
     * Sets the childVersion value for this Relation.
     * 
     * @param childVersion
     */
    public void setChildVersion(java.lang.String childVersion) {
        this.childVersion = childVersion;
    }


    /**
     * Gets the geometry value for this Relation.
     * 
     * @return geometry
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Attachment getGeometry() {
        return geometry;
    }


    /**
     * Sets the geometry value for this Relation.
     * 
     * @param geometry
     */
    public void setGeometry(com.woodwing.enterprise.interfaces.services.wfl.Attachment geometry) {
        this.geometry = geometry;
    }


    /**
     * Gets the rating value for this Relation.
     * 
     * @return rating
     */
    public org.apache.axis.types.UnsignedInt getRating() {
        return rating;
    }


    /**
     * Sets the rating value for this Relation.
     * 
     * @param rating
     */
    public void setRating(org.apache.axis.types.UnsignedInt rating) {
        this.rating = rating;
    }


    /**
     * Gets the targets value for this Relation.
     * 
     * @return targets
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Target[] getTargets() {
        return targets;
    }


    /**
     * Sets the targets value for this Relation.
     * 
     * @param targets
     */
    public void setTargets(com.woodwing.enterprise.interfaces.services.wfl.Target[] targets) {
        this.targets = targets;
    }


    /**
     * Gets the parentInfo value for this Relation.
     * 
     * @return parentInfo
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ObjectInfo getParentInfo() {
        return parentInfo;
    }


    /**
     * Sets the parentInfo value for this Relation.
     * 
     * @param parentInfo
     */
    public void setParentInfo(com.woodwing.enterprise.interfaces.services.wfl.ObjectInfo parentInfo) {
        this.parentInfo = parentInfo;
    }


    /**
     * Gets the childInfo value for this Relation.
     * 
     * @return childInfo
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ObjectInfo getChildInfo() {
        return childInfo;
    }


    /**
     * Sets the childInfo value for this Relation.
     * 
     * @param childInfo
     */
    public void setChildInfo(com.woodwing.enterprise.interfaces.services.wfl.ObjectInfo childInfo) {
        this.childInfo = childInfo;
    }


    /**
     * Gets the objectLabels value for this Relation.
     * 
     * @return objectLabels
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ObjectLabel[] getObjectLabels() {
        return objectLabels;
    }


    /**
     * Sets the objectLabels value for this Relation.
     * 
     * @param objectLabels
     */
    public void setObjectLabels(com.woodwing.enterprise.interfaces.services.wfl.ObjectLabel[] objectLabels) {
        this.objectLabels = objectLabels;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Relation)) return false;
        Relation other = (Relation) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.parent==null && other.getParent()==null) || 
             (this.parent!=null &&
              this.parent.equals(other.getParent()))) &&
            ((this.child==null && other.getChild()==null) || 
             (this.child!=null &&
              this.child.equals(other.getChild()))) &&
            ((this.type==null && other.getType()==null) || 
             (this.type!=null &&
              this.type.equals(other.getType()))) &&
            ((this.placements==null && other.getPlacements()==null) || 
             (this.placements!=null &&
              java.util.Arrays.equals(this.placements, other.getPlacements()))) &&
            ((this.parentVersion==null && other.getParentVersion()==null) || 
             (this.parentVersion!=null &&
              this.parentVersion.equals(other.getParentVersion()))) &&
            ((this.childVersion==null && other.getChildVersion()==null) || 
             (this.childVersion!=null &&
              this.childVersion.equals(other.getChildVersion()))) &&
            ((this.geometry==null && other.getGeometry()==null) || 
             (this.geometry!=null &&
              this.geometry.equals(other.getGeometry()))) &&
            ((this.rating==null && other.getRating()==null) || 
             (this.rating!=null &&
              this.rating.equals(other.getRating()))) &&
            ((this.targets==null && other.getTargets()==null) || 
             (this.targets!=null &&
              java.util.Arrays.equals(this.targets, other.getTargets()))) &&
            ((this.parentInfo==null && other.getParentInfo()==null) || 
             (this.parentInfo!=null &&
              this.parentInfo.equals(other.getParentInfo()))) &&
            ((this.childInfo==null && other.getChildInfo()==null) || 
             (this.childInfo!=null &&
              this.childInfo.equals(other.getChildInfo()))) &&
            ((this.objectLabels==null && other.getObjectLabels()==null) || 
             (this.objectLabels!=null &&
              java.util.Arrays.equals(this.objectLabels, other.getObjectLabels())));
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
        if (getParent() != null) {
            _hashCode += getParent().hashCode();
        }
        if (getChild() != null) {
            _hashCode += getChild().hashCode();
        }
        if (getType() != null) {
            _hashCode += getType().hashCode();
        }
        if (getPlacements() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getPlacements());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getPlacements(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getParentVersion() != null) {
            _hashCode += getParentVersion().hashCode();
        }
        if (getChildVersion() != null) {
            _hashCode += getChildVersion().hashCode();
        }
        if (getGeometry() != null) {
            _hashCode += getGeometry().hashCode();
        }
        if (getRating() != null) {
            _hashCode += getRating().hashCode();
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
        if (getParentInfo() != null) {
            _hashCode += getParentInfo().hashCode();
        }
        if (getChildInfo() != null) {
            _hashCode += getChildInfo().hashCode();
        }
        if (getObjectLabels() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getObjectLabels());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getObjectLabels(), i);
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
        new org.apache.axis.description.TypeDesc(Relation.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Relation"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("parent");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Parent"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("child");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Child"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("type");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Type"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "RelationType"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("placements");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Placements"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Placement"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Placement"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("parentVersion");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ParentVersion"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("childVersion");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ChildVersion"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("geometry");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Geometry"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Attachment"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("rating");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Rating"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("targets");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Targets"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Target"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Target"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("parentInfo");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ParentInfo"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ObjectInfo"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("childInfo");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ChildInfo"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ObjectInfo"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("objectLabels");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ObjectLabels"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ObjectLabel"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ObjectLabel"));
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
